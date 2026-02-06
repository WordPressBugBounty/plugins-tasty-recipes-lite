import { useState } from '@wordpress/element';

const defaultModalData = {
	open: false,
	id: '',
	title: '',
	upgradeUrl:
		'https://www.wptasty.com/pricing-recipes?utm_source=WordPress&utm_medium=tasty-framework&utm_campaign=modal&utm_content=template',
	previewImage: '',
	closeCallback: null,
	xClickCallback: null,
};

export const useModal = () => {
	const [ modalData, setModalData ] = useState( defaultModalData );

	const openModal = ( innerModalData ) => {
		setModalData( ( prev ) => {
			return {
				...prev,
				...innerModalData,
				open: true,
			};
		} );
	};

	const disableClosingModal = () => {
		setModalData( ( prev ) => {
			return {
				...prev,
				disableClosing: true,
			};
		} );
	};

	const closeModal = ( force = false ) => {
		if ( modalData.disableClosing && ! force ) {
			return;
		}

		if ( modalData.closeCallback ) {
			modalData.closeCallback();
		}

		setModalData( defaultModalData );
	};

	const generateUpgradeUrl = ( {
		medium = 'tasty-framework',
		campaign = 'modal',
		content = 'template',
	} ) => {
		return `https://www.wptasty.com/pricing-recipes?utm_source=WordPress&utm_medium=${ medium }&utm_campaign=${ campaign }&utm_content=${ content }`;
	};

	return {
		modalData,
		openModal,
		closeModal,
		generateUpgradeUrl,
		disableClosingModal,
	};
};
