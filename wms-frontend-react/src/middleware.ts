import { getAuthCookie } from './utils/cookie';

export const authGuard = (): boolean => {
  const token = getAuthCookie('auth_token');
  return !!token; 
};
