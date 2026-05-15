(function () {
	'use strict';

	if (typeof window.stlAdmin === 'undefined') {
		return;
	}

	function setBusy(input, busy) {
		input.disabled = !!busy;
	}

	function toggle(input) {
		var slug = input.getAttribute('data-slug');
		if (!slug) return;

		var enable = input.checked ? 1 : 0;
		var prev   = !input.checked;

		setBusy(input, true);

		var body = new URLSearchParams();
		body.append('action', 'stl_toggle_widget');
		body.append('nonce', window.stlAdmin.nonce);
		body.append('slug', slug);
		body.append('enable', String(enable));

		fetch(window.stlAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		})
			.then(function (res) { return res.json().catch(function () { return null; }); })
			.then(function (data) {
				if (!data || !data.success) {
					input.checked = prev;
				}
			})
			.catch(function () {
				input.checked = prev;
			})
			.then(function () {
				setBusy(input, false);
			});
	}

	document.addEventListener('change', function (e) {
		var t = e.target;
		if (t && t.matches && t.matches('.stl-switch input[type="checkbox"][data-slug]')) {
			toggle(t);
		}
	});
})();
