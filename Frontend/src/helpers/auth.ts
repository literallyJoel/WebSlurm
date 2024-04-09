import crypto from "crypto-js";
import { apiEndpoint } from "@/config/config";

export type LoginRequest = {
  email: string;
  password: string;
};

export type LoginResponse = {
  token: string;
};

export type User = {
  id: string;
  name: string;
  email: string;
  role: number;
  isOrgAdmin: boolean;
};

export type TokenData = {
  exp: number;
  userId: string;
  email: string;
  name: string;
  role: number;
  requiresPasswordReset: "0" | "1";
  local?: boolean;
  isOrgAdmin: boolean;
};

type VerifyTokenResponse = { ok: boolean; err?: number };

type VerifyPasswordResponse = { ok: boolean };

export const login = async (
  loginRequest: LoginRequest
): Promise<LoginResponse> => {
  const password = loginRequest.password;
  const hashedPassword = crypto.SHA512(password).toString();

  loginRequest.password = hashedPassword;
  const response = await fetch(`${apiEndpoint}/auth/login`, {
    method: "POST",
    body: JSON.stringify(loginRequest),
  });

  if (response.ok) {
    return await response.json();
  } else {
    return Promise.reject(new Error(response.statusText));
  }
};

export const logout = async (token: string) => {
  const response = await fetch(`${apiEndpoint}/auth/logout`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });

  if (response.ok) {
    return await response;
  }
  return Promise.reject(new Error(response.statusText));
};

export const verifyToken = async (
  token: string
): Promise<VerifyTokenResponse> => {
  const resp = await fetch(`${apiEndpoint}/auth/verify`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });

  if (resp.status === 200) {
    return { ok: true };
  } else {
    if ((await resp.text()) === "Token Expired") {
      return { ok: false, err: 4011 };
    }

    return { ok: false, err: resp.status };
  }
};

export const refreshToken = async (token: string): Promise<LoginResponse> => {
  const response = await fetch(`${apiEndpoint}/auth/refresh`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const verifyPass = async (
  password: string,
  token: string
): Promise<VerifyPasswordResponse> => {
  const hashedPassword = crypto.SHA512(password).toString();

  const response = await fetch(`${apiEndpoint}/auth/verifypass`, {
    method: "POST",
    body: JSON.stringify({ password: hashedPassword }),
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const disableUserTokens = async (token: string): Promise<Response> => {
  const response = await fetch(`${apiEndpoint}/auth/disabletokens`, {
    method: "POST",
    headers: {
      Authorization: `bearer ${token}`,
    },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response;
};
