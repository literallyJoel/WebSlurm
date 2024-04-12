import {
  OrganisationUser,
  removeUserFromOrganisation,
  setUserRole,
} from "@/helpers/organisations";
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
import { useParams } from "react-router-dom";
import Noty from "noty";
export const columns = (refetch: Function) => {
  const column: ColumnDef<OrganisationUser>[] = [
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
        const user = row.original;
        const userId = user.userId;

        if (userId) if (role === 0) return "User";
        if (role === 1) return "Moderator";
        if (role === 2) return "Admin";
      },
    },
    {
      id: "actions",
      cell: ({ row }) => {
        const user = row.original;
        const currentUser = useAuthContext().getUser();
        const token = useAuthContext().getToken();
        const { organisationId } = useParams();
        const _makeUserAdmin = useMutation(
          "makeUserAdmin",
          () => {
            return setUserRole(token, organisationId!, user.userId, 2);
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
        const _makeUserModerator = useMutation(
          "makeUserModerator",
          () => {
            return setUserRole(token, organisationId!, user.userId, 1);
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
                text: "User succesfully made moderator",
                type: "success",
                timeout: 4000,
              }).show();
            },
          }
        );
        const _makeUserUser = useMutation(
          "makeUserUser",
          () => {
            return setUserRole(token, organisationId!, user.userId, 0);
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
        const _removeFromOrg = useMutation(
          "removeUser",
          () => {
            return removeUserFromOrganisation(
              token,
              user.userId,
              organisationId!
            );
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
                  <DropdownMenuLabel>User Role</DropdownMenuLabel>
                  {Number(user.role) !== 0 && (
                    <DropdownMenuItem
                      className="cursor-pointer"
                      onClick={() => _makeUserUser.mutate()}
                    >
                      Demote to User
                    </DropdownMenuItem>
                  )}
                  {Number(user.role) !== 1 && (
                    <DropdownMenuItem
                      className="cursor-pointer"
                      onClick={() => _makeUserModerator.mutate()}
                    >
                      {Number(user.role) === 0
                        ? "Promote to Moderator"
                        : "Demote to Moderator"}
                    </DropdownMenuItem>
                  )}
                  {Number(user.role) !== 2 && (
                    <DropdownMenuItem
                      className="cursor-pointer"
                      onClick={() => _makeUserAdmin.mutate()}
                    >
                      Promote to Admin
                    </DropdownMenuItem>
                  )}
                  <DropdownMenuSeparator />
                  <DropdownMenuLabel>Actions</DropdownMenuLabel>
                  <DropdownMenuItem
                    onClick={() => _removeFromOrg.mutate()}
                    className="cursor-pointer"
                  >
                    Remove from Organisation
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
