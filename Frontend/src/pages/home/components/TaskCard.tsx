import { Badge } from "@/shadui/ui/badge";

interface props {
  taskName: string;
  taskID: string;
  variant: "Failed" | "Completed" | "Running";
  startTime: string;
  endTime?: string;
  runTime?: string;
}

export const TaskCard = ({taskName, taskID, variant, startTime, endTime, runTime}: props): JSX.Element => {
  return (
    <div className="flex justify-between items-center">
      <p className="text-sm font-medium">{taskName}</p>
      <Badge className={`${variant === "Running" ? "bg-uol" : variant === "Failed" ? "bg-red-400" : "bg-emerald-500"}`}>
        {variant}
      </Badge>
    </div>
  );
};
