import { Badge } from "../shadui/ui/badge";
import { msToTimeString } from "@/helpers/misc";
interface props {
  name: string;
  id: string;
  variant: "Failed" | "Completed" | "Running";
  startTime: Date;
  endTime?: Date;
  runTime?: number;
}


const TaskCard = ({ name, variant, endTime, runTime }: props): JSX.Element => {
  return (
    <div className="flex justify-between items-center">
      <p className="text-sm font-medium">{name}</p>
      <Badge
        className={`${
          variant === "Running"
            ? "bg-uol"
            : variant === "Failed"
            ? "bg-red-400"
            : "bg-emerald-500"
        }`}
      >
        {variant === "Completed"
          ? `Completed at ${endTime?.toLocaleDateString()}`
          : variant === "Failed"
          ? `Job Failed`
          : `Running for ${msToTimeString(runTime ?? 0)}`}
      </Badge>
    </div>
  );
};

export default TaskCard;
