import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { Badge } from "@/shadui/ui/badge";
import { Button } from "@/shadui/ui/button";
import { Card, CardFooter, CardHeader, CardTitle } from "@/shadui/ui/card";
import { useContext } from "react";
import { useMutation, useQueryClient } from "react-query";
import { deleteAccount } from "@/helpers/accounts";

interface props {
  id: string;
  name: string;
  role: number;
  userCount?: number;
}

const UserCard = ({ id, name, role, userCount }: props): JSX.Element => {
  const queryClient = useQueryClient();
  const token = useContext(AuthContext).getToken();
  const deleteUser = useMutation(
    "deleteCard",
    (id: string) => {
      return deleteAccount(id, token);
    },
    {
      onSettled: () => {
        queryClient.invalidateQueries("getAllUsers");
      },
    }
  );

  return (
    <Card className="relative">
      {role === 1 && (
        <div className="flex flex-row w-full absolute right0">
          <Badge className="bg-emerald-500 rounded rounded-b-none rounded-tr-none w-12 text-xs justify-center">
            Admin
          </Badge>
        </div>
      )}

      <CardHeader>
        <div className="flex flex-col justify-between w-full gap-2 border-b border-b-uol pb-2">
          <CardTitle className="pt-2 w-full">{name}</CardTitle>
        </div>
      </CardHeader>

      <CardFooter className="flex flex-row justify-center items-center">
        <Button
          onClick={() => deleteUser.mutate(id)}
          className="bg-red-500 border border-red-500 text-white hover:bg-transparent hover:text-red-500"
          disabled={userCount === 1 ?? true}
        >
          Delete
        </Button>
      </CardFooter>
    </Card>
  );
};

export default UserCard;
