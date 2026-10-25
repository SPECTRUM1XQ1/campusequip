/**
 * Swipe carousel with a "peek" of the next card. No Previous / Next buttons.
 *
 * Markup (see public/borrower/my_requests.php):
 *   <div class="snap-carousel" data-carousel>
 *     <div class="snap-track"> <div class="snap-slide">card</div> ... </div>
 *     <div class="snap-dots"></div>
 *   </div>
 *
 * The browser does the real work: the track is a horizontally scrolling flex row with CSS scroll-snap, so
 * touch swipe, trackpad and arrow keys work natively and feel smooth. This script only adds:
 *   - drag-to-swipe with a mouse (touch and trackpads already scroll by themselves)
 *   - .is-active on the cards that are (mostly) in view, so the cards peeking at the edge look dimmed
 *   - the page dots (they follow the swipe; tapping one jumps to that card)
 *   - soft edge fades only on the side that has more cards
 *   - a one-time nudge the first time a row appears, hinting that it can be swiped
 */
(function () {
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function init(root) {
        const track = root.querySelector('.snap-track');
        const dotsBox = root.querySelector('.snap-dots');
        const slides = Array.from(track.children);
        if (!slides.length) return;

        // ---------- Active slides (IntersectionObserver, so no scroll-handler maths) ----------
        const visible = new Set();
        const io = new IntersectionObserver(entries => {
            entries.forEach(en => {
                en.target.classList.toggle('is-active', en.isIntersecting);
                if (en.isIntersecting) visible.add(en.target); else visible.delete(en.target);
            });
            paintDots();
            paintEdges();
        }, { root: track, threshold: 0.6 });
        slides.forEach(s => io.observe(s));

        // ---------- Dots (a counter instead when there are many cards) ----------
        let dots = [];
        let counter = null;
        if (dotsBox) {
            if (slides.length > 1 && slides.length <= 8) {
                dots = slides.map((slide, i) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'snap-dot';
                    dot.tabIndex = -1;
                    dot.addEventListener('click', () => slide.scrollIntoView({
                        behavior: reduceMotion ? 'auto' : 'smooth', inline: 'start', block: 'nearest'
                    }));
                    dotsBox.appendChild(dot);
                    return dot;
                });
                dotsBox.removeAttribute('aria-hidden');
            } else if (slides.length > 8) {
                counter = document.createElement('span');
                counter.className = 'snap-counter';
                dotsBox.appendChild(counter);
            }
        }

        function paintDots() {
            const idx = slides.map((s, i) => visible.has(s) ? i : -1).filter(i => i >= 0);
            dots.forEach((d, i) => d.classList.toggle('is-on', idx.includes(i)));
            if (counter && idx.length) {
                const from = idx[0] + 1, to = idx[idx.length - 1] + 1;
                counter.textContent = (from === to ? from : from + '–' + to) + ' of ' + slides.length;
            }
        }

        // ---------- Edge fades: only on a side that still has cards ----------
        function paintEdges() {
            const max = track.scrollWidth - track.clientWidth;
            root.classList.toggle('can-prev', track.scrollLeft > 4);
            root.classList.toggle('can-next', max - track.scrollLeft > 4);
        }
        track.addEventListener('scroll', paintEdges, { passive: true });
        window.addEventListener('resize', paintEdges);
        paintEdges();

        // ---------- Mouse drag-to-swipe (touch / trackpad scroll natively) ----------
        let startX = 0, startLeft = 0, dragging = false, moved = false;

        track.addEventListener('pointerdown', e => {
            if (e.pointerType !== 'mouse' || e.button !== 0) return;
            if (e.target.closest('button, a, input, select, textarea')) return;
            dragging = true; moved = false;
            startX = e.clientX; startLeft = track.scrollLeft;
        });
        window.addEventListener('pointermove', e => {
            if (!dragging) return;
            const dx = e.clientX - startX;
            if (!moved && Math.abs(dx) > 5) {
                moved = true;
                track.classList.add('is-dragging');   // turns snapping off while the card follows the pointer
            }
            if (moved) track.scrollLeft = startLeft - dx;
        });
        function endDrag() {
            if (!dragging) return;
            dragging = false;
            if (!moved) return;
            // Let go on the nearest card: re-enable snapping and glide to it.
            const target = slides.reduce((best, s) =>
                Math.abs(s.offsetLeft - track.scrollLeft) < Math.abs(best.offsetLeft - track.scrollLeft) ? s : best, slides[0]);
            track.classList.remove('is-dragging');
            track.scrollTo({ left: target.offsetLeft - slides[0].offsetLeft, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);
        // a drag must not count as a click on a card
        track.addEventListener('click', e => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
        track.addEventListener('dragstart', e => e.preventDefault());

        // ---------- One-time nudge the first time this row is on screen ----------
        if (!reduceMotion) {
            const nudge = new IntersectionObserver(entries => {
                if (!entries.some(en => en.isIntersecting)) return;
                nudge.disconnect();
                if (track.scrollWidth - track.clientWidth > 8) {
                    root.classList.add('nudge');
                    setTimeout(() => root.classList.remove('nudge'), 1600);
                }
            }, { threshold: 0.5 });
            nudge.observe(root);
        }
    }

    document.querySelectorAll('[data-carousel]').forEach(init);
})();
