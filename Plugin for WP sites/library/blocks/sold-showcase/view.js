document.querySelectorAll('.sold-showcase').forEach(function (block) {
	var slides = block.querySelectorAll('.sold-showcase__slide');
	if (slides.length <= 1) return;

	var strip = block.querySelector('.sold-showcase__strip');
	var prev = block.querySelector('.sold-showcase__nav--prev');
	var next = block.querySelector('.sold-showcase__nav--next');
	var dots = block.querySelectorAll('.sold-showcase__dot');
	var current = 0;
	var total = slides.length;
	var pointerStartX = 0;
	var pointerCurrentX = 0;
	var dragging = false;
	var dragThreshold = 40;

	function update() {
		strip.style.transform = 'translateX(' + (current * -100) + '%)';
		dots.forEach(function (dot, index) {
			dot.classList.toggle('is-active', index === current);
		});
		slides.forEach(function (slide, index) {
			slide.setAttribute('aria-hidden', index === current ? 'false' : 'true');
		});
	}

	function show(index) {
		current = (index + total) % total;
		update();
	}

	function startDrag(clientX) {
		pointerStartX = clientX;
		pointerCurrentX = clientX;
		dragging = true;
		strip.classList.add('is-dragging');
	}

	function moveDrag(clientX) {
		if (!dragging) return;
		pointerCurrentX = clientX;
		var delta = clientX - pointerStartX;
		strip.style.transform = 'translateX(calc(' + (current * -100) + '% + ' + delta + 'px))';
	}

	function endDrag() {
		if (!dragging) return;
		dragging = false;
		strip.classList.remove('is-dragging');
		var delta = pointerCurrentX - pointerStartX;
		if (Math.abs(delta) >= dragThreshold) {
			show(delta < 0 ? current + 1 : current - 1);
			return;
		}
		update();
	}

	if (prev) prev.addEventListener('click', function () { show(current - 1); });
	if (next) next.addEventListener('click', function () { show(current + 1); });

	block.setAttribute('tabindex', '0');
	block.addEventListener('keydown', function (event) {
		if (event.key === 'ArrowLeft') show(current - 1);
		if (event.key === 'ArrowRight') show(current + 1);
	});

	block.addEventListener('touchstart', function (event) {
		startDrag(event.touches[0].clientX);
	}, { passive: true });

	block.addEventListener('touchmove', function (event) {
		moveDrag(event.touches[0].clientX);
	}, { passive: true });

	block.addEventListener('touchend', endDrag);
	block.addEventListener('touchcancel', endDrag);

	block.addEventListener('mousedown', function (event) {
		startDrag(event.clientX);
	});

	block.addEventListener('mousemove', function (event) {
		moveDrag(event.clientX);
	});

	block.addEventListener('mouseup', endDrag);
	block.addEventListener('mouseleave', endDrag);

	update();
});
