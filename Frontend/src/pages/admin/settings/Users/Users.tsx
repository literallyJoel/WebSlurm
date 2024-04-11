import { useQuery } from "react-query";
import { Button } from "@/components/shadui/ui/button";
import { FaPlus } from "react-icons/fa";
import { Link } from "react-router-dom";
import { getAllUsers, getUserCount } from "@/helpers/users";
import { useAuthContext } from "@/providers/AuthProvider";
import { DataTable } from "@/components/Table/data-table";
import { columns } from "@/components/Table/columns/users";

const Users = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const allUsers = useQuery("getAllUsers", () => {
    return getAllUsers(token);
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

      {allUsers.data && (
        <div className="w-full h-full">
          <DataTable columns={columns(allUsers.refetch)} data={allUsers.data} />
        </div>
      )}
    </div>
  );
};

export default Users;
