import { useAuthContext } from "@/providers/AuthProvider";
import { ColumnDef } from "@tanstack/react-table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/shadui/ui/dropdown-menu";
import { Button } from "@/components/shadui/ui/button";
import { ArrowUpDown, MoreHorizontal } from "lucide-react";
import { Badge } from "@/components/shadui/ui/badge";
import { useMutation } from "react-query";
import Noty from "noty";
import { User, resetUserPassword } from "@/helpers/users";
import { deleteAccount, makeUserAdmin, removeUserAdmin } from "@/helpers/users";
export const columns = (refetch: Function) => {
  const column: ColumnDef<User>[] = [
    {
      accessorKey: "userName",
      header: ({ column }) => {
        return (
          <Button
            className="flex flex-row justify-center items-center w-full"
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Name
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
      cell: ({ row }) => {
        const userId = row.original.userId;
        const currentUser = useAuthContext().getUser().id;
        const name = row.getValue("userName");
        return (
          <div className="flex flex-row justify-center gap-4 relative">
            {userId === currentUser && (
              <Badge className="absolute left-0">You</Badge>
            )}
            <div>{name as string}</div>
          </div>
        );
      },
    },
    {
      accessorKey: "userEmail",
      header: ({ column }) => {
        return (
          <Button
            className="flex flex-row justify-center items-center w-full"
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Email
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
    },

    {
      accessorKey: "role",
      header: ({ column }) => {
        return (
          <Button
            className="flex flex-row justify-center items-center w-full"
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Role
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
      cell: ({ row }) => {
        const role = Number(row.getValue("role"));
        return role === 0 ? "User" : "Admin";
      },
    },
    {
      id: "actions",
      cell: ({ row }) => {
        const user = row.original;
        const currentUser = useAuthContext().getUser();
        const token = useAuthContext().getToken();
        const _makeUserAdmin = useMutation(
          "makeUserAdmin",
          () => {
            return makeUserAdmin(token, user.userId);
          },
          {
            onError: () => {
              new Noty({
                text: "Something went wrong, please try again later",
                type: "error",
                timeout: 4000,
              }).show();
            },
            onSuccess: () => {
              refetch();
              new Noty({
                text: "User promoted succesfully",
                type: "success",
                timeout: 4000,
              }).show();
            },
          }
        );

        const _removeUserAdmin = useMutation(
          "removeUserAdmin",
          () => {
            return removeUserAdmin(token, user.userId);
          },
          {
            onError: () => {
              new Noty({
                text: "Something went wrong, please try again later",
                type: "error",
                timeout: 4000,
              }).show();
            },
            onSuccess: () => {
              refetch();
              new Noty({
                text: "User demoted succesfully",
                type: "success",
                timeout: 4000,
              }).show();
            },
          }
        );
        const _deleteUser = useMutation(
          "deleteUser",
          () => {
            return deleteAccount(token, user.userId);
          },
          {
            onError: () => {
              new Noty({
                text: "Something went wrong, please try again later",
                type: "error",
                timeout: 4000,
              }).show();
            },
            onSuccess: () => {
              refetch();
              new Noty({
                text: "User removed succesfully",
                type: "success",
                timeout: 4000,
              }).show();
            },
          }
        );

        const _resetPassword = useMutation(
          "resetPassword",
          () => {
            return resetUserPassword(token, user.userId);
          },
          {
            onSuccess: () => {
              new Noty({
                text: "Succesfully reset users password. They will recieve an email containing a temporary password",
                type: "success",
                timeout: 5000,
              }).show();
            },
            onError: () => {
              new Noty({
                text: "Failed to reset users password. Please try again later.",
                type: "error",
                timeout: 5000,
              }).show();
            },
          }
        );
        return (
          <>
            {currentUser.id !== user.userId && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreHorizontal className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuLabel>Actions</DropdownMenuLabel>
                  {Number(user.role) === 0 ? (
                    <DropdownMenuItem
                      className="cursor-pointer"
                      onClick={() => _makeUserAdmin.mutate()}
                    >
                      Promote to Admin
                    </DropdownMenuItem>
                  ) : (
                    <DropdownMenuItem
                      className="cursor-pointer"
                      onClick={() => _removeUserAdmin.mutate()}
                    >
                      Demote to User
                    </DropdownMenuItem>
                  )}

                  <DropdownMenuItem
                    className="cursor-pointer"
                    onClick={() => _resetPassword.mutate()}
                  >
                    Reset Password
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuLabel className="text-red-800">
                    Destructive Actions
                  </DropdownMenuLabel>
                  <DropdownMenuItem
                    onClick={() => _deleteUser.mutate()}
                    className="cursor-pointer text-red-500"
                  >
                    Delete User
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </>
        );
      },
    },
  ];

  return column;
};
