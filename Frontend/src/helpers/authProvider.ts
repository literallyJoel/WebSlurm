export type decodedToken = {
  exp: number;
  userID: string;
  email: string;
  name: string;
  privLevel: number;
  requiresPasswordReset: boolean;
  local: boolean;
};

export async function verifyToken(token: string): Promise<boolean> {
  const resp = await fetch("/api/auth/verify", {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  });

  return resp.status === 200;
}
