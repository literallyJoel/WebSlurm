import { Badge } from "@/shadui/ui/badge";

interface props {
  name: string;
  id: string;
  variant: "Failed" | "Completed" | "Running";
  startTime: Date;
  endTime?: Date;
  runTime?: number;
}

const msToTimeString = (epochTime: number): String => {
  // Convert milliseconds to seconds
  const seconds = Math.floor(epochTime / 1000);

  // Calculate days, hours, and minutes
  const days = Math.floor(seconds / (24 * 60 * 60));
  const hours = Math.floor((seconds % (24 * 60 * 60)) / (60 * 60));
  const minutes = Math.floor((seconds % (60 * 60)) / 60);

  // Format the result
  let result = "";
  if (days > 0) {
    result += days + " Day" + (days !== 1 ? "s" : "");
  }
  if (hours > 0) {
    result += (result ? ", " : "") + hours + " Hour" + (hours !== 1 ? "s" : "");
  }
  if (minutes > 0 || (days === 0 && hours === 0)) {
    result +=
      (result ? ", " : "") + minutes + " Minute" + (minutes !== 1 ? "s" : "");
  }

  return result || "0 Minutes"; // Default to 0 minutes if the input is 0
};

const TaskCard = ({
  name,
  id,
  variant,
  startTime,
  endTime,
}: props): JSX.Element => {
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
          ? `Completed at ${startTime.toLocaleString()}`
          : variant === "Failed"
          ? `Failed at ${endTime?.toLocaleString()}`
          : `Running for ${msToTimeString(Date.now() - startTime.getTime())}`}
      </Badge>
    </div>
  );
};

export default TaskCard;
