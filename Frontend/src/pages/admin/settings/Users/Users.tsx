import { useQuery } from "react-query";
import { Button } from "@/shadui/ui/button";
import { FaPlus } from "react-icons/fa";
import UserCard from "./components/UserCard";
import { Link } from "react-router-dom";
import { getAllAccounts, getUserCount } from "@/helpers/accounts";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

const Users = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const allUsers = useQuery("getAllUsers", () => {
    return getAllAccounts(token);
  });
  const userCount = useQuery("getUserCount", () => {
    return getUserCount(token);
  });
  return (
    <div className="w-full flex flex-col">
      <span className="text-2xl text-uol font-bold">Users</span>
      <div className="w-full flex flex-row justify-center p-4">
        <Link to="/accounts/create">
          <Button className="bg-tranparent border-green-600 border hover:bg-green-600 group transition-colors">
            <FaPlus className="text-green-600 group-hover:text-white transition-colors" />
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-4">
        {allUsers.data?.map((user) => {
          return (
            <UserCard
              key={user.userID}
              id={`${user.userID}`}
              name={user.userName}
              role={user.role}
              userCount={userCount.data?.count}
            />
          );
        })}
      </div>
    </div>
  );
};

export default Users;
