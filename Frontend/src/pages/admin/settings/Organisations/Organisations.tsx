import { useMutation, useQuery } from "react-query";
import { Button } from "@/components/shadui/ui/button";
import { FaPlus } from "react-icons/fa";
import { Link } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider";
import { getOrganisation, updateOrganisation } from "@/helpers/organisations";
import React, { useState } from "react";
import { DataTable } from "@/components/Table/data-table";
import { columns } from "@/components/Table/columns/organisations";
import {
  CardTitle,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/components/shadui/ui/card";
import { Label } from "@/components/shadui/ui/label";
import { Input } from "@/components/shadui/ui/input";
import { XCircle } from "lucide-react";
import Noty from "noty";

interface props {
  selectedOrganisation: string;
  setIsRenameModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
  refetch: Function;
}
const RenameModal = ({
  setIsRenameModalOpen,
  selectedOrganisation,
  refetch,
}: props): JSX.Element => {
  const [organisationName, setOrganisationName] = useState("");
  const token = useAuthContext().getToken();
  const updateName = useMutation(
    `update${selectedOrganisation}Name`,
    () => {
      return updateOrganisation(token, selectedOrganisation, organisationName);
    },
    {
      onSuccess: () => {
        setIsRenameModalOpen(false);
        new Noty({
          text: "Organisation name updated successfully",
          type: "success",
          timeout: 4000,
        }).show();
        refetch();
      },
      onError: () => {
        new Noty({
          text: "Failed to update organisation name. Please try again later.",
          type: "error",
          timeout: 4000,
        }).show();
      },
    }
  );
  return (
    <div className="absolute w-full h-full flex flex-row left-0 top-0 z-10 justify-center bg-uol/10 items-center backdrop-blur-sm">
      <Card className="w-6/12 shadow-lg">
        <CardHeader className="grid grid-cols-3">
          <div></div>
          <div className="flex flex-row w-full justify-center items-center">
            <CardTitle className="text-center">
              Change Organization Name
            </CardTitle>
          </div>
          <div className="flex flex-row justify-end">
            <XCircle
              className="text-red-700 cursor-pointer hover:text-red-700/70"
              onClick={() => setIsRenameModalOpen(false)}
            />
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="new-name">New Name</Label>
            <Input
              value={organisationName}
              onChange={(e) => setOrganisationName(e.target.value)}
              id="new-name"
              placeholder="Enter the new name"
            />
          </div>
        </CardContent>
        <CardFooter className="flex gap-2">
          <Button className="ml-auto" onClick={() => updateName.mutate()}>
            Save
          </Button>
          <Button variant="outline" onClick={() => setIsRenameModalOpen(false)}>
            Close
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
};

const Organisations = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const [organisationCount, setOrganisationCount] = useState(1);
  const [selectedOrganisation, setSelectedOrganisation] = useState("");
  const [isRenameModalOpen, setIsRenameModalOpen] = useState(false);

  const { data: allOrganisations, refetch } = useQuery(
    "getAllOrganisations",
    () => {
      return getOrganisation(token);
    },
    {
      onSuccess: (data) => setOrganisationCount(data.length),
    }
  );

  return (
    <div className="w-full flex flex-col">
      {isRenameModalOpen && (
        <RenameModal
          setIsRenameModalOpen={setIsRenameModalOpen}
          selectedOrganisation={selectedOrganisation}
          refetch={refetch}
        />
      )}
      <span className="text-2xl text-uol font-bold">Organisations</span>
      <div className="w-full flex flex-row justify-center p-4">
        <Link to="/organisations/create">
          <Button className="bg-tranparent border-green-600 border hover:bg-green-600 group transition-colors">
            <FaPlus className="text-green-600 group-hover:text-white transition-colors" />
          </Button>
        </Link>
      </div>

      <div className="w-full">
        <DataTable
          data={allOrganisations ?? []}
          columns={columns(
            refetch,
            organisationCount,
            setSelectedOrganisation,
            setIsRenameModalOpen
          )}
        />
      </div>
    </div>
  );
};

export default Organisations;
