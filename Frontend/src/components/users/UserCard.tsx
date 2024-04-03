import { Badge } from "@/components/shadui/ui/badge";
import { Button } from "@/components/shadui/ui/button";
import {
  Card,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { useMutation, useQueryClient } from "react-query";
import { deleteAccount } from "@/helpers/users";
import { useAuthContext } from "@/providers/AuthProvider";
import Noty from "noty";
interface props {
  id: string;
  name: string;
  role: number;
  userCount?: number;
}

const UserCard = ({ id, name, role, userCount }: props): JSX.Element => {
  const queryClient = useQueryClient();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const user = authContext.getUser();

  const deleteUser = useMutation(
    "deleteCard",
    (id: string) => {
      return deleteAccount(token, id);
    },
    {
      onSettled: () => {
        queryClient.invalidateQueries("getAllUsers");
      },
      onSuccess: () => {
        const noty = new Noty({
          type: "success",
          text: "User deleted successfully.",
          timeout: 4000,
        });
        noty.show();
      },
      onError: () => {
        const noty = new Noty({
          type: "error",
          text: "Failed to delete user. Please try again later.",
          timeout: 4000,
        });
        noty.show();
      },
    }
  );

  return (
    <Card className="relative">
      <div className="flex flex-row w-full absolute right-0 gal-0">
        {Number(role) === 1 && (
          <Badge className="bg-emerald-500 rounded rounded-b-none rounded-tr-none w-12 text-xs justify-center">
            Admin
          </Badge>
        )}
        {user?.id === id && (
          <Badge className="bg-orange-500 rounded-none w-12 text-xs justify-center">
            You
          </Badge>
        )}
      </div>

      {user?.id === id && (
        <div className="flex flex-row w-full absolute -right-10"></div>
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
          disabled={(userCount === 1 ?? true) || user?.id === id}
        >
          Delete
        </Button>
      </CardFooter>
    </Card>
  );
};

export default UserCard;
