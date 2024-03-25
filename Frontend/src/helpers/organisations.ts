import { apiEndpoint } from "@/config/config";
export type NewOrganisation = {
  organisationName: string;
  ownerId: string;
};

export type Organisation = NewOrganisation & {
  organisationID: string;
  ownerName: string;
};
export async function getAllOrganisations(
  token: string
): Promise<Organisation[]> {
  return (
    await fetch(apiEndpoint + "/organisations/getAll", {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
}

export async function getOrganisation(
  organisationId: string,
  token: string
): Promise<Organisation> {
  return (
    await fetch(`/api/organisations/${organisationId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
}

export async function deleteOrganisation(
  organisationId: string,
  token: string
): Promise<Response> {
  return await fetch(`/api/organisations/${organisationId}`, {
    method: "DELETE",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function createOrganisation(
  organisationName: string,
  ownerId: string,
  token: string
): Promise<{ orgId: string }> {
  return (
    await fetch(apiEndpoint + "/organisations/create", {
      method: "POST",
      body: JSON.stringify({
        organisationName,
        ownerId,
      }),
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
}
