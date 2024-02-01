import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { Badge } from "@/shadui/ui/badge";
import { Button } from "@/shadui/ui/button";
import { Card, CardFooter, CardHeader, CardTitle } from "@/shadui/ui/card";
import { useContext } from "react";
import { useMutation, useQueryClient } from "react-query";
import { Link } from "react-router-dom";
import { deleteJobType } from "../jobTypes";

interface props {
  id: string;
  name: string;
  createdBy: string;
}

const JobCard = ({ id, name, createdBy }: props): JSX.Element => {
  const queryClient = useQueryClient();
  const token = useContext(AuthContext).getToken();
  const deleteJT = useMutation(
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
          <Badge className="bg-uol justify-center text-sm min-w-3/12 self-end">
            Created By: {createdBy}
          </Badge>
        </div>
        <CardTitle className="pt-2">{name}</CardTitle>
      </CardHeader>

      <CardFooter className="flex flex-row justify-between items-center">
        <Link to={`/admin/jobtypes/${id}`}>
          <Button className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white">
            Modify
          </Button>
        </Link>
        <Button
          onClick={() => deleteJT.mutate(id)}
          className="bg-red-500 border border-red-500 text-white hover:bg-transparent hover:text-red-500"
        >
          Delete
        </Button>
      </CardFooter>
    </Card>
  );
};

export default JobCard;
