import React from 'react';

export interface ImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
  src: string;
  alt: string;
  width?: number | string;
  height?: number | string;
  fill?: boolean;
  priority?: boolean;
  quality?: number;
  unoptimized?: boolean;
}

export const Image: React.FC<ImageProps> = ({
  src,
  alt,
  width,
  height,
  fill,
  priority,
  quality,
  unoptimized,
  className = '',
  style = {},
  ...props
}) => {
  // If src is root-relative (like /images/podcast_cover.jpg), resolve to current base path if needed
  let resolvedSrc = src;
  if (src && src.startsWith('/')) {
    // In subfolder deployment like /love-talkpodcast2/public/, check base
    const base = document.querySelector('base')?.getAttribute('href') || '';
    if (base && !src.startsWith(base)) {
      resolvedSrc = `${base.replace(/\/$/, '')}${src}`;
    }
  }

  const fillStyles: React.CSSProperties = fill
    ? {
        position: 'absolute',
        top: 0,
        left: 0,
        bottom: 0,
        right: 0,
        width: '100%',
        height: '100%',
        objectFit: 'cover',
      }
    : {};

  return (
    <img
      src={resolvedSrc}
      alt={alt || ''}
      width={width}
      height={height}
      className={className}
      style={{ ...fillStyles, ...style }}
      loading={priority ? 'eager' : 'lazy'}
      {...props}
    />
  );
};

export default Image;
