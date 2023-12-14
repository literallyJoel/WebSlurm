import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from "@/shadui/ui/card";
import { Button } from "@/shadui/ui/button";
import { Badge } from "@/shadui/ui/badge";

interface props {
  jobName: string;
  jobID: string;
  createdBy: string;
}

export const JobCard = ({ jobName, jobID, createdBy }: props): JSX.Element => {
  return (
    <Card>
      <CardHeader>
        <div className="flex flex-row justify-between w-full border-b border-b-uol pb-2">
          <Badge className="bg-uol min-w-4/12 justify-center">
            Job ID: {jobID}
          </Badge>
          <Badge className="bg-uol justify-center min-w-3/12 self-end">
            Created By: {createdBy}
          </Badge>
        </div>

        <CardTitle className="pt-2">{jobName}</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col justify-between items-center">
        <Button className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white">
          Modify
        </Button>
      </CardContent>
    </Card>
  );
};
