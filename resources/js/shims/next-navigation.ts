import { useLocation, useNavigate, useParams as useRouteParams, useSearchParams as useRouterSearchParams } from 'react-router-dom';

export function usePathname(): string {
  const location = useLocation();
  return location.pathname;
}

export function useRouter() {
  const navigate = useNavigate();
  return {
    push: (href: string) => navigate(href),
    replace: (href: string) => navigate(href, { replace: true }),
    back: () => navigate(-1),
    forward: () => navigate(1),
    refresh: () => window.location.reload(),
    prefetch: () => {},
  };
}

export function useSearchParams(): URLSearchParams {
  const [searchParams] = useRouterSearchParams();
  return searchParams;
}

export function useParams<T extends Record<string, string> = Record<string, string>>(): T {
  return useRouteParams() as T;
}

export function notFound(): never {
  throw new Error('NEXT_NOT_FOUND');
}

export function redirect(url: string): never {
  window.location.href = url;
  throw new Error('NEXT_REDIRECT');
}
