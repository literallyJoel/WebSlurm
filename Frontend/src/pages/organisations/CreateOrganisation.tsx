import Nav from "@/components/Nav";
import { Button } from "@/components/shadui/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { Input } from "@/components/shadui/ui/input";
import { createOrganisation } from "@/helpers/organisations";
import { getAllUsers } from "@/helpers/users";
import { Label } from "@radix-ui/react-label";
import React, { useState } from "react";
import { useMutation, useQuery } from "react-query";
import Noty from "noty";

import { ComboBoxItem, Combobox } from "@/components/shadui/ui/combobox";
import { useNavigate } from "react-router-dom";

interface props {
  isSetup?: boolean;
  setOrganisationName?: React.Dispatch<React.SetStateAction<string>>;
}
export const CreateOrganisation = ({
  isSetup,
  setOrganisationName: _setOrgName,
}: props): JSX.Element => {
  const navigate = useNavigate();
  const token = localStorage.getItem("token") ?? "";
  const [organisationName, setOrganisationName] = useState("");
  const [formattedUserArray, setFormattedUserArray] =
    useState<ComboBoxItem[]>();
  const [selectedUser, setSelectedUser] = useState("");
  useQuery(
    "getUsers",
    () => {
      return getAllUsers(token);
    },
    {
      enabled: !isSetup,
      onSuccess: (data) => {
        const formattedData = data.map((user) => ({
          label: user.userName,
          value: user.userId,
        }));
        setFormattedUserArray(formattedData);
      },
    }
  );
  const createOrg = useMutation(
    "createOrg",
    () => {
      return createOrganisation(token, organisationName, selectedUser!);
    },
    {
      onError: () => {
        new Noty({
          type: "error",
          text: "Failed to create organisation. Please try again later.",
          timeout: 2000,
        }).show();
      },
      onSuccess: () => {
        new Noty({
          type: "success",
          text: "Organisation created successfully. You will be redirected momentarily.",
          timeout: 2000,
        }).show();

        setTimeout(() => {
          navigate("/admin/organisations");
        }, 2000);
      },
    }
  );

  const _createOrganisation = () => {
    if (isSetup) {
      _setOrgName!(() => organisationName);
    } else {
      createOrg.mutate();
    }
  };
  return (
    <div className="flex flex-col h-screen">
      <Nav />
      {/* These exist because I can't use the navigate hook so I have to create invisible links and click them programatically. */}

      <div className="mt-10 flex-grow mb-10">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle>Create a Organisation</CardTitle>
            {isSetup && (
              <CardDescription>
                Create an organistion to get started. You will be an
                administrator for this organisation.
              </CardDescription>
            )}
          </CardHeader>
          <CardContent className="flex flex-col gap-2">
            <Label>Organisation Name</Label>
            <Input
              type="text"
              value={organisationName}
              onChange={(e) => setOrganisationName(e.target.value)}
            />

            {!isSetup && !!formattedUserArray && (
              <>
                <Label>Select an Administrator for this Organisation</Label>
                <Combobox
                  items={formattedUserArray ?? []}
                  value={selectedUser ?? ""}
                  setValue={setSelectedUser}
                  itemTypeName="User"
                />
              </>
            )}
          </CardContent>
          <CardFooter className="justify-center">
            <Button
              onClick={() => _createOrganisation()}
              disabled={
                isSetup
                  ? organisationName === ""
                  : organisationName === "" || selectedUser === ""
              }
              className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
            >
              Create Organisation
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};

export default CreateOrganisation;
