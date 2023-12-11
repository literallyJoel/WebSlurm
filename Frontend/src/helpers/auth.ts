import crypto from "crypto-js";
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

type VerfiyPasswordResponse = {
  ok: boolean;
}
export async function login(loginObject: LoginObject): Promise<LoginResponse> {
  const pass = loginObject.password;
  const hashedPass = crypto.SHA512(pass).toString();
  loginObject.password = hashedPass;
  return (
    await fetch("/api/auth/login", {
      method: "POST",
      body: JSON.stringify(loginObject),
    })
  ).json();
}

export async function logout(token: string) {
  return await fetch("/api/auth/logout", {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });
}

export async function verifyPass(pass: string, token: string): Promise<VerfiyPasswordResponse> {
  const hashedPass = crypto.SHA512(pass).toString();
  return await fetch("/api/auth/verifypass", {
    method: "POST",
    body: JSON.stringify({ password: hashedPass }),
    headers: {Authorization: `Bearer ${token}`}
  });
}
