import { apiEndpoint } from "@/config/config";
import { JobType } from "./jobTypes";
export type Organisation = {
  organisationId: string;
  organisationName: string;
};
export type OrganisationUser = {
  userName: string;
  userEmail: string;
  globalRole: number;
  role: number;
  userId: string;
};

export const getOrganisation = async (
  token: string,
  organisationId?: string
): Promise<Organisation[]> => {
  const endpoint = organisationId
    ? `${apiEndpoint}/organisations/${organisationId}`
    : `${apiEndpoint}/organisations`;
  const response = await fetch(endpoint, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const organisations = await response.json();

  return Array.isArray(organisations) ? organisations : [organisations];
};

export const createOrganisation = async (
  token: string,
  organisationName: string,
  adminId: string
): Promise<Response> => {
  const response = await fetch(`${apiEndpoint}/organisations`, {
    method: "POST",
    body: JSON.stringify({ organisationName, adminId }),
    headers: { Authorization: `Bearer ${token}` },
  });

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const deleteOrganisation = async (
  token: string,
  organisationId: string
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}`,
    {
      method: "DELETE",
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const updateOrganisation = async (
  token: string,
  organisationId: string,
  organisationName: string
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}`,
    {
      method: "PATCH",
      body: JSON.stringify({ organisationName }),
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const setUserRole = async (
  token: string,
  organisationId: string,
  userId: string,
  role: number
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users`,
    {
      method: "PATCH",
      body: JSON.stringify({ userId, role }),
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const getOrganisationUsers = async (
  token: string,
  organisationId: string,
  userId?: string
): Promise<OrganisationUser[]> => {
  let endpoint = `${apiEndpoint}/organisations/${organisationId}/users`;
  endpoint = userId ? `${endpoint}/${userId}` : endpoint;

  const response = await fetch(endpoint, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const users = await response.json();

  return Array.isArray(users) ? users : [users];
};

export const getOrganisationAdmins = async (
  token: string,
  organisationId: string,
  userId?: string
): Promise<OrganisationUser[]> => {
  let endpoint = `${apiEndpoint}/organisations/${organisationId}/admins`;
  endpoint = userId ? `${endpoint}/${userId}` : endpoint;

  const response = await fetch(endpoint, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const users = await response.json();
  return Array.isArray(users) ? users : [users];
};

export const getOrganisationModerators = async (
  token: string,
  organisationId: string,
  userId?: string
): Promise<OrganisationUser[]> => {
  let endpoint = `${apiEndpoint}/organisations/${organisationId}/moderators`;
  endpoint = userId ? `${endpoint}/${userId}` : endpoint;

  const response = await fetch(endpoint, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const users = await response.json();

  return Array.isArray(users) ? users : [users];
};

export const getUserOrganisations = async (
  token: string,
  userId?: string,
  role?: number
): Promise<Organisation[]> => {
  let endpoint = `${apiEndpoint}/organisations/users/getorganisations`;
  endpoint = role ? `${endpoint}/${role}` : endpoint;

  const response = await fetch(endpoint, {
    method: "POST",
    body: JSON.stringify({ userId }),
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const organisations = await response.json();

  return Array.isArray(organisations) ? organisations : [organisations];
};

export const removeUserFromOrganisation = async (
  token: string,
  userId: string,
  organisationId: string
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users/remove`,
    {
      body: JSON.stringify({ userId }),
      method: "POST",
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const addUserToOrganisation = async (
  token: string,
  userEmail: string,
  organisationId: string,
  role: number
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users`,
    {
      method: "POST",
      body: JSON.stringify({ userEmail, role }),
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return response.ok
    ? response
    : Promise.reject(new Error(response.statusText));
};

export const getOrganisationJobTypes = async (
  token: string,
  organisationId: string
): Promise<JobType[]> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/jobtypes`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const jobTypes = await response.json();

  return Array.isArray(jobTypes) ? jobTypes : [jobTypes];
};
