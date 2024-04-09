import { apiEndpoint } from "@/config/config";
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
export const createOrganisation = async (
  token: string,
  name: string,
  userId: string
) => {
  const response = await fetch(`${apiEndpoint}/organisations`, {
    method: "POST",
    body: JSON.stringify({ organisationName: name, adminId: userId }),
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return response;
};

export const deleteOrganisation = async (
  token: string,
  organisationId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}`,
    {
      method: "DELETE",
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response;
};

export const updateOrganisation = async (
  token: string,
  organisationId: string,
  organisationName: string
) => {
  const response = await fetch(`${apiEndpoint}/organisations/`, {
    method: "PUT",
    body: JSON.stringify({ organisationName, organisationId }),
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response;
};

export const addUserToOrg = async (
  token: string,
  organisationId: string,
  userEmail: string,
  role: 0 | 1 | 2
) => {
  const response = await fetch(`${apiEndpoint}/organisations/user`, {
    method: "POST",
    body: JSON.stringify({ organisationId, userEmail, role }),
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response;
};

export const removeUserFromOrg = async (
  token: string,
  organisationId: string,
  userId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/${userId}`,
    {
      method: "DELETE",
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response;
};

export const getOrganisationUsers = async (
  token: string,
  organisationId: string
): Promise<OrganisationUser[]> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const users = await response.json();
  return Array.isArray(users) ? users : [users];
};

export const getOrganisationUser = async (
  token: string,
  organisationId: string,
  userId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/users/${organisationId}/${userId}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const getOrganisationAdmins = async (
  token: string,
  organisationId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/admins`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const getAllOrganisations = async (
  token: string
): Promise<Organisation[]> => {
  const response = await fetch(`${apiEndpoint}/organisations`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const getOrganisation = async (
  token: string,
  organisationId: string
): Promise<Organisation> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  const org = await response.json();
  return Array.isArray(org) ? org[0] : org;
};

export const getUserMemberships = async (
  token: string,
  userId?: string
): Promise<Organisation[]> => {
  const endpoint = userId
    ? `${apiEndpoint}/organisations/user/${userId}`
    : `${apiEndpoint}/organisations/user`;
  const response = await fetch(endpoint, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const getAdminsOrgs = async (token: string): Promise<Organisation[]> => {
  const response = await fetch(`${apiEndpoint}/organisations/admin`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  const orgs = await response.json();

  return Array.isArray(orgs) ? orgs : [orgs];
};

export const makeUserAdmin = async (
  token: string,
  userId: string,
  organisationId: string
): Promise<Response> => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users/admin`,
    {
      method: "PATCH",
      headers: { Authorization: `Bearer ${token}` },
      body: JSON.stringify({ userId }),
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return response;
};

export const makeUserModerator = async (
  token: string,
  userId: string,
  organisationId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users/moderator`,
    {
      method: "PATCH",
      headers: { Authorization: `Bearer ${token}` },
      body: JSON.stringify({ userId }),
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return response;
};

export const makeUserUser = async (
  token: string,
  userId: string,
  organisationId: string
) => {
  const response = await fetch(
    `${apiEndpoint}/organisations/${organisationId}/users/user`,
    {
      method: "PATCH",
      headers: { Authorization: `Bearer ${token}` },
      body: JSON.stringify({ userId }),
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return response;
};
