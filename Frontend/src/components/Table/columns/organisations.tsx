import {
  type Organisation,
  deleteOrganisation,
  getOrganisationAdmins,
  getOrganisationModerators,
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
import { useMutation, useQuery } from "react-query";
import { useNavigate } from "react-router-dom";
import Noty from "noty";
import React from "react";
export const columns = (
  refetch: Function,
  count: number,
  setSelectedOrganisation: React.Dispatch<React.SetStateAction<string>>,
  setIsRenameModalOpen: React.Dispatch<React.SetStateAction<boolean>>
) => {
  const column: ColumnDef<Organisation>[] = [
    {
      accessorKey: "organisationName",
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
        const organisation = row.original;
        const authContext = useAuthContext();
        const token = authContext.getToken();
        const user = authContext.getUser();
        const { data: isAdmin } = useQuery(
          `getIsAdminOf${organisation.organisationId}`,
          async () => {
            const admins = await getOrganisationAdmins(
              token,
              organisation.organisationId,
              user.id
            );

            return admins && admins.length !== 0;
          }
        );

        const { data: isModerator } = useQuery(
          `getIsModeratorOf${organisation.organisationId}`,
          async () => {
            const moderators = await getOrganisationModerators(
              token,
              organisation.organisationId,
              user.id
            );

            return moderators && moderators.length !== 0;
          }
        );

        return (
          <div className="flex flex-row justify-center items-center gap-2">
            {isAdmin && <Badge>Admin</Badge>}
            {isModerator && <Badge className="bg-yellow-500">Moderator</Badge>}
            {organisation.organisationName}
          </div>
        );
      },
    },

    {
      id: "actions",
      cell: ({ row }) => {
        const navigate = useNavigate();
        const organisation = row.original;
        const token = useAuthContext().getToken();

        const _deleteOrganisation = useMutation(
          "removeUser",
          () => {
            return deleteOrganisation(token, organisation.organisationId);
          },
          {
            onError: () => {
              new Noty({
                text: "Something went wrong, please try again later.",
                type: "error",
                timeout: 4000,
              }).show();
            },
            onSuccess: () => {
              refetch();
              new Noty({
                text: "Organisation deleted successfully.",
                type: "success",
                timeout: 4000,
              }).show();
            },
          }
        );
        return (
          <>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                <DropdownMenuItem
                  onClick={() => {
                    setSelectedOrganisation(organisation.organisationId);
                    setIsRenameModalOpen(true);
                  }}
                  className="cursor-pointer"
                >
                  Rename Organisation
                </DropdownMenuItem>
                <DropdownMenuItem
                  className="cursor-pointer"
                  onClick={() =>
                    navigate(`/organisations/${organisation.organisationId}`)
                  }
                >
                  Organisation Management
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel className="text-red-800">
                  Destructive Actions
                </DropdownMenuLabel>
                <DropdownMenuItem
                  onClick={() => _deleteOrganisation.mutate()}
                  className="cursor-pointer text-red-500"
                  disabled={count === 0}
                >
                  Delete Organisation
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </>
        );
      },
    },
  ];

  return column;
};
