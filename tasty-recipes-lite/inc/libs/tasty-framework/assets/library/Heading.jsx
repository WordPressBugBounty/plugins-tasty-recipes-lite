import classNames from 'classnames';

export const Heading = ( { children, type = 'h2', className = '' } ) => {
	const acceptedTags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

	if ( ! acceptedTags.includes( type ) ) {
		return null;
	}

	const HeadingTag = type;
	const commonClass = classNames( 'tasty-heading', className );

	return <HeadingTag className={ commonClass }>{ children }</HeadingTag>;
};
