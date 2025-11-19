document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-timeline-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        if (! track) {
            return;
        }

        const scrollAmount = () => track.clientWidth * 0.8;
        const scrollTrack = (direction) => {
            track.scrollBy({ left: direction * scrollAmount(), behavior: 'smooth' });
        };

        slider.querySelectorAll('[data-slider-prev]').forEach((button) => {
            button.addEventListener('click', () => scrollTrack(-1));
        });

        slider.querySelectorAll('[data-slider-next]').forEach((button) => {
            button.addEventListener('click', () => scrollTrack(1));
        });

        const interceptWheelScroll = (container) => {
            if (! container) {
                return;
            }

            container.addEventListener('wheel', (event) => {
                if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                container.scrollBy({
                    left: event.deltaY * -1,
                    behavior: 'auto',
                });
            }, { passive: false });
        };

        interceptWheelScroll(track);
        slider.querySelectorAll('.timeline-period-cards').forEach(interceptWheelScroll);
    });
});
