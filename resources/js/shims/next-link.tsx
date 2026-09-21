import React from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

export interface LinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
  href: string;
  replace?: boolean;
  scroll?: boolean;
  prefetch?: boolean;
  children?: React.ReactNode;
}

export const Link: React.FC<LinkProps> = ({ href, children, replace, onClick, ...props }) => {
  const isExternal = href.startsWith('http://') || href.startsWith('https://') || href.startsWith('mailto:') || href.startsWith('tel:');

  if (isExternal) {
    return (
      <a href={href} target="_blank" rel="noopener noreferrer" onClick={onClick} {...props}>
        {children}
      </a>
    );
  }

  return (
    <RouterLink to={href} replace={replace} onClick={onClick} {...props}>
      {children}
    </RouterLink>
  );
};

export default Link;
