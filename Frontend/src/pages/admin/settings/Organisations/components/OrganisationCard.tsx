import { Badge } from "@/shadui/ui/badge";
import { Button } from "@/shadui/ui/button";
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadui/ui/card";
import { useMutation, useQueryClient } from "react-query";
import { Link } from "react-router-dom";
import { deleteJobType } from "../../../../../helpers/jobTypes";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

interface props {
  id: string;
  name: string;
  ownerName: string
}

const OrganisationCard = ({ id, name, ownerName }: props): JSX.Element => {
  const queryClient = useQueryClient();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const deleteOrganisation = useMutation(
    "deleteCard",
    (id: string) => {
      return deleteJobType(id, token);
    },
    {
      onSettled: () => {
        queryClient.invalidateQueries("getAllTypes");
      },
    }
  );

  return (
    <Card>
      <CardHeader>
        <div className="flex flex-row justify-between w-full gap-2 border-b border-b-uol pb-2">
          <Badge className="bg-uol min-w-3/12 text-sm justify-center">
            ID: {id}
          </Badge>
        </div>
        <CardTitle className="pt-2">{name}</CardTitle>
        <CardDescription>Owner: {ownerName}</CardDescription>
      </CardHeader>

      <CardFooter className="flex flex-row justify-between items-center">
        <Link to={`/admin/organisations/${id}`}>
          <Button className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white">
            User Management
          </Button>
        </Link>
        <Button
          onClick={() => deleteOrganisation.mutate(id)}
          className="bg-red-500 border border-red-500 text-white hover:bg-transparent hover:text-red-500"
        >
          Delete
        </Button>
      </CardFooter>
    </Card>
  );
};

export default OrganisationCard;
