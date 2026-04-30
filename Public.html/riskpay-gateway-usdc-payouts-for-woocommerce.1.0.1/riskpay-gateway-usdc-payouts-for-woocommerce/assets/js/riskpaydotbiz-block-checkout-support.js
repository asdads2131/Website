( function( blocks, i18n, element, components, editor ) {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    // Use the localized data from PHP
    const riskpaydotbizs = riskpaydotbizData || [];
    riskpaydotbizs.forEach( ( riskpaydotbiz ) => {
        registerPaymentMethod({
            name: riskpaydotbiz.id,
            label: riskpaydotbiz.label,
            ariaLabel: riskpaydotbiz.label,
            content: element.createElement(
                'div',
                { className: 'riskpaydotbiz-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'riskpaydotbiz-method-label' },
                    '' + riskpaydotbiz.description 
                ),
                riskpaydotbiz.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: riskpaydotbiz.icon_url,
                        alt: riskpaydotbiz.label,
                        className: 'riskpaydotbiz-method-icon'
                    }
                ) : null
            ),
            edit: element.createElement(
                'div',
                { className: 'riskpaydotbiz-method-wrapper' },
                element.createElement( 
                    'div', 
                    { className: 'riskpaydotbiz-method-label' },
                    '' + riskpaydotbiz.description 
                ),
                riskpaydotbiz.icon_url ? element.createElement(
                    'img', 
                    { 
                        src: riskpaydotbiz.icon_url,
                        alt: riskpaydotbiz.label,
                        className: 'riskpaydotbiz-method-icon'
                    }
                ) : null
            ),
            canMakePayment: () => true,
        });
    });
} )(
    window.wp.blocks,
    window.wp.i18n,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor
);