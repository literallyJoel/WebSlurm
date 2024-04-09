import { Badge } from "@/components/shadui/ui/badge";
import { Button } from "@/components/shadui/ui/button";
import {
  Card,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { useMutation, useQueryClient } from "react-query";
import { useAuthContext } from "@/providers/AuthProvider";
import Noty from "noty";
import { Organisation, deleteOrganisation } from "@/helpers/organisations";
import { useEffect, useState } from "react";
interface props {
  id: string;
  name: string;
  organisationCount: number;
  userMemberships: Organisation[];
}

const OrganisationCard = ({
  id,
  name,
  organisationCount,
  userMemberships,
}: props): JSX.Element => {
  const queryClient = useQueryClient();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const [isUserInOrg, setIsUserInOrg] = useState(false);
  const _deleteOrganisation = useMutation(
    "deleteOrg",
    (id: string) => {
      return deleteOrganisation(token, id);
    },
    {
      onSettled: () => {
        queryClient.invalidateQueries("getAllOrganisations");
      },
      onSuccess: () => {
        const noty = new Noty({
          type: "success",
          text: "Organisation deleted successfully.",
          timeout: 4000,
        });
        noty.show();
      },
      onError: () => {
        const noty = new Noty({
          type: "error",
          text: "Failed to delete organisation. Please try again later.",
          timeout: 4000,
        });
        noty.show();
      },
    }
  );

  useEffect(() => {
    if (id && userMemberships && userMemberships.length !== 0) {
      setIsUserInOrg(userMemberships.some((org) => org.organisationId === id));
    }
  }, [id, userMemberships]);
  return (
    <Card className="relative">
      <div className="flex flex-row w-full absolute right-0 gal-0">
        {isUserInOrg && (
          <Badge className="bg-emerald-500 rounded rounded-b-none rounded-tr-none w-13 text-xs justify-center">
            Member
          </Badge>
        )}
      </div>

      <CardHeader>
        <div className="flex flex-col justify-between w-full gap-2 border-b border-b-uol pb-2">
          <CardTitle className="pt-2 w-full">{name}</CardTitle>
        </div>
      </CardHeader>
      <CardFooter className="flex flex-row justify-center items-center">
        <Button
          onClick={() => _deleteOrganisation.mutate(id)}
          className="bg-red-500 border border-red-500 text-white hover:bg-transparent hover:text-red-500"
          disabled={organisationCount === 1 ?? true}
        >
          Delete
        </Button>
      </CardFooter>
    </Card>
  );
};

export default OrganisationCard;
