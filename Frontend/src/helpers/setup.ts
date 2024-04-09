import { apiEndpoint } from "@/config/config";
import crypto from "crypto-js";
export type SetupResponse = {
  userId: string;
  organisationId: number;
};
export type SetupRequest = {
  userName: string;
  email: string;
  password: string;
  organisationName: string;
};
export const getShouldSetup = async (): Promise<{ shouldSetup: boolean }> => {
  const response = await fetch(`${apiEndpoint}/setup/shouldsetup`);
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const createInitial = async (
  setupRequest: SetupRequest
): Promise<SetupResponse> => {
  const hashedPass = crypto.SHA512(setupRequest.password).toString();
  const response = await fetch(`${apiEndpoint}/setup/createinitial`, {
    method: "POST",
    body: JSON.stringify({ ...setupRequest, password: hashedPass }),
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};
