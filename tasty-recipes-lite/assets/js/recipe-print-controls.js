(function(){
	const iframe = document.createElement('iframe');
	iframe.frameBorder = 0;
	iframe.id = 'tasty-recipes-print-controls';
	iframe.height = 51;
	document.body.prepend(iframe);

	const resizeIframe = () => {
		iframe.height = iframe.contentWindow.document.querySelector('#tr-control-form').getBoundingClientRect().height;
	};

	iframe.onload = () => {
		resizeIframe();
	};

	iframe.contentWindow.document.open();
	iframe.contentWindow.document.write(document.querySelector('#tmpl-tasty-recipes-print-controls').innerHTML);
	iframe.contentWindow.document.close();

	const controlForm = iframe.contentWindow.document.querySelector('#tr-control-form');
	controlForm.querySelector('#tasty-recipes-print').addEventListener('click', () => {
		window.print();
	});
	controlForm.querySelector('#tasty-recipes-options').addEventListener('click', (event) => {
		const el = event.target;
		if (el.classList.contains('active')) {
			el.classList.remove('active');
			controlForm.classList.remove('tasty-recipes-show-controls');
		} else {
			el.classList.add('active');
			controlForm.classList.add('tasty-recipes-show-controls');
		}
		resizeIframe();
		/* Prevent Safari from printing the iframe. */
		window.focus();
	});

	controlForm.querySelectorAll('input').forEach((input) => {
		input.addEventListener('change', () => {
			applyPrintBodyClasses();
			/* Prevent Safari from printing the iframe. */
			window.focus();
		});
	});

	const applyPrintBodyClasses = () => {
		controlForm.querySelectorAll('.tasty-recipes-print-display').forEach((input) => {
			const displayClass = input.id;
			const noDisplayClass = input.id.replace('-display-', '-hide-');
			if (input.checked) {
				document.body.classList.add(displayClass);
				document.body.classList.remove(noDisplayClass);
			} else {
				document.body.classList.remove(displayClass);
				document.body.classList.add(noDisplayClass);
			}
		});
		controlForm.querySelectorAll('.tasty-recipes-print-text-size').forEach((input) => {
			if (input.checked) {
				document.body.classList.add(input.id);
			} else {
				document.body.classList.remove(input.id);
			}
		});
	};
	applyPrintBodyClasses();
}());
