import { useAuthContext } from "@/providers/AuthProvider";
import { ColumnDef } from "@tanstack/react-table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/shadui/ui/dropdown-menu";
import { Button } from "@/components/shadui/ui/button";
import { ArrowUpDown, MoreHorizontal } from "lucide-react";
import { useMutation } from "react-query";
import { useNavigate } from "react-router-dom";
import Noty from "noty";
import { JobType, deleteJobType } from "@/helpers/jobTypes";
export const columns = (refetch: Function) => {
  const column: ColumnDef<JobType>[] = [
    {
      accessorKey: "jobTypeName",
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
    },
    {
      accessorKey: "createdByName",
      header: ({ column }) => {
        return (
          <Button
            className="flex flex-row justify-center items-center w-full"
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Created By
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
    },
    {
      id: "actions",
      cell: ({ row }) => {
        const jobType = row.original;
        const token = useAuthContext().getToken();
        const navigate = useNavigate();

        const _deleteJobType = useMutation(
          "deleteJobType",
          () => {
            return deleteJobType(jobType.jobTypeId, token);
          },
          {
            onSuccess: () => {
              refetch();
              new Noty({
                type: "success",
                text: "Job Type Deleted Successfully",
                timeout: 5000,
              }).show();
            },
            onError: () => {
              new Noty({
                type: "error",
                text: "Failed to delete Job Type. Please try again later",
                timeout: 5000,
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
                <DropdownMenuItem
                  className="cursor-pointer"
                  onClick={() =>
                    navigate(`/admin/jobtypes/${jobType.jobTypeId}`)
                  }
                >
                  Edit Job Type
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem
                  onClick={() => _deleteJobType.mutate()}
                  className="cursor-pointer"
                >
                  Delete Job Type
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
