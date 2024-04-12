import { useAuthContext } from "@/providers/AuthProvider";
import { useMutation, useQuery } from "react-query";
import { useParams } from "react-router-dom";
import { GrStatusUnknown } from "react-icons/gr";
import {
  addUserToOrganisation,
  getOrganisation,
  getOrganisationJobTypes,
  getOrganisationUsers,
} from "@/helpers/organisations";

import { DataTable } from "@/components/Table/data-table";
import { columns as jobTypeColumns } from "@/components/Table/columns/jobtypes";
import { columns as userColumns } from "@/components/Table/columns/organisationUsers";
import { Input } from "@/components/shadui/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/shadui/ui/select";
import { useState } from "react";
import { Label } from "@radix-ui/react-label";
import { Button } from "@/components/shadui/ui/button";
import Noty from "noty";
import Spinner from "@/components/Spinner/Spinner";

const OrganisationInfo = (): JSX.Element => {
  const { organisationId } = useParams();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const [newUserEmail, setNewUserEmail] = useState("");
  const [newUserRole, setNewUserRole] = useState<0 | 1 | 2>(0);
  const { data: organisation } = useQuery(
    `org${organisationId}Info`,
    () => getOrganisation(token, organisationId!),
    { enabled: !!organisationId }
  );

  const { data: users, refetch: refetchUsers } = useQuery(
    `${organisationId}-users`,
    () => getOrganisationUsers(token, organisationId!),
    { enabled: !!organisationId }
  );

  const { data: jobTypes, refetch: refetchJobTypes } = useQuery(
    `${organisationId}-jobTypes`,
    () => {
      return getOrganisationJobTypes(token, organisationId!);
    },
    {
      onError: () => {
        new Noty({
          type: "error",
          text: "Failed to fetch job types. Please try again later.",
          timeout: 4000,
        }).show();
      },
    }
  );
  const _addUserToOrg = useMutation(
    "addUserToOrg",
    () => {
      return addUserToOrganisation(
        token,
        newUserEmail,
        organisationId!,
        newUserRole
      );
    },
    {
      onError: () => {
        new Noty({
          type: "error",
          text: "Something went wrong, please try again later",
          timeout: 4000,
        }).show();
      },
      onSuccess: () => {
        new Noty({
          type: "success",
          text: "If the provided user exists, they have been added to your organisation.",
          timeout: 4000,
        }).show();
        refetchUsers();
      },
    }
  );

  if (!organisation) {
    return (
      <div className="flex h-full flex-col gap-2 items-center justify-center">
        <GrStatusUnknown className="text-8xl text-uol animate-blueBounce" />
        <div className="text-2xl">
          Specified organisation does not exist or you do not have permission to
          access it.
        </div>
      </div>
    );
  }

  if (!jobTypes) {
    return (
      <div className="flex h-full flex-col gap-2 items-center justify-center">
        <Spinner />
        <div className="text-2xl">Loading</div>
      </div>
    );
  }
  return (
    <div className="flex flex-col gap-2">
      <div className="text-4xl font-bold">
        {organisation[0].organisationName}
      </div>
      {users && (
        <div className="w-full flex flex-col h-full justify-center overflow-y-auto border">
          <div className="text-xl font-bold pt-8 p-2">Users</div>
          <DataTable columns={userColumns(refetchUsers)} data={users} />
        </div>
      )}

      <div className="w-full border">
        {users && (
          <>
            <div className="text-xl font-bold p-2 max-h-50vh ">Add User</div>
            <div className="p-2 flex flex-row gap-2">
              <div className="flex flex-col w-full">
                <Label htmlFor="email">Email</Label>
                <Input
                  value={newUserEmail}
                  onChange={(e) => setNewUserEmail(e.target.value)}
                  id="email"
                  type="text"
                  placeholder="Email"
                  className="border w-full p-2"
                />
              </div>

              <div className="flex flex-col w-full max-h-[50vh]">
                <Label>Role</Label>
                <Select
                  required
                  value={`${newUserRole}`}
                  onValueChange={(e) => {
                    setNewUserRole(Number.parseInt(e) as 0 | 1 | 2);
                  }}
                >
                  <SelectTrigger className="border border-[#E2E8F0] h-10 rounded-md relative">
                    <SelectValue placeholder="Select a parameter type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="0">User</SelectItem>
                    <SelectItem value="1">Moderator</SelectItem>
                    <SelectItem value="2">Admin</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="p-2">
              <Button
                disabled={newUserEmail === ""}
                className="w-1/2"
                onClick={() => _addUserToOrg.mutate()}
              >
                Add User
              </Button>
            </div>
          </>
        )}
      </div>
      {jobTypes && (
        <div className="w-full flex flex-col  max-h-[50vh] justify-center overflow-y-auto border">
          <div className="text-xl font-bold p-5 pt-6">Job Types</div>
          <DataTable
            columns={jobTypeColumns(refetchJobTypes)}
            data={jobTypes}
          />
        </div>
      )}
    </div>
  );
};

export default OrganisationInfo;
