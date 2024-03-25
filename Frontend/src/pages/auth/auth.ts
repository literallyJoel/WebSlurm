import crypto from "crypto-js";
import { apiEndpoint } from "@/config/config";
export type LoginObject = {
  email: string;
  password: string;
};

export type LoginResponse = {
  token: string;
};

export type User = {
  name: string;
  email: string;
  role: number;
  id: string;
};

export type DecodedToken = {
  exp: number;
  userID: string;
  email: string;
  name: string;
  role: number;
  requiresPasswordReset: boolean;
  local: boolean;
};

type VerifyPasswordResponse = { ok: boolean };

export async function login(loginObject: LoginObject): Promise<LoginResponse> {
  const pass = loginObject.password;
  //We hash the password on this end also just so our server never even sees the plaintext password
  const hashedPass = crypto.SHA512(pass).toString();
  loginObject.password = hashedPass;
  return (
    await fetch(apiEndpoint + "/auth/login", {
      method: "POST",
      body: JSON.stringify(loginObject),
    })
  ).json();
}

export async function logout(token: string) {
  return await fetch(apiEndpoint + "/auth/logout", {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });
}

export async function verifyPass(
  pass: string,
  token: string
): Promise<VerifyPasswordResponse> {
  const hashedPass = crypto.SHA512(pass).toString();
  return await fetch(apiEndpoint + "/auth/verifypass", {
    method: "POST",
    body: JSON.stringify({ password: hashedPass }),
    headers: { Authorization: `Bearer ${token}` },
  });
}

export async function verifyToken(
  token: string
): Promise<{ ok: boolean; err?: number }> {
  const resp = await fetch(apiEndpoint + "/auth/verify", {
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
}
