import React from 'react';

export default function SimdexLogo({ variant = 'mark', className = '' }) {
    return (
        <img
            className={`simdex-logo ${variant}${className ? ` ${className}` : ''}`}
            src={variant === 'full' ? '/images/simdex-logo.png' : '/images/simdex-mark.png'}
            alt={variant === 'full' ? 'SIMDEX - Student Index Management' : 'SIMDEX'}
        />
    );
}
