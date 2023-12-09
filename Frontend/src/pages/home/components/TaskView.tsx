import { Button } from "@/shadui/ui/button";
import { CardTitle, CardHeader, CardContent, Card } from "@/shadui/ui/card";
import { TaskCard } from "./TaskCard";

interface props {
  failedJobs: Array<{
    taskName: string;
    taskID: string;
    startTime: string;
    endTime?: string;
    runTime?: string;
  }>;
  completedJobs: Array<{
    taskName: string;
    taskID: string;
    startTime: string;
    endTime?: string;
    runTime?: string;
  }>;
  runningJobs: Array<{
    taskName: string;
    taskID: string;
    startTime: string;
    endTime?: string;
    runTime?: string;
  }>;
}
export const TaskView = ({
  failedJobs,
  completedJobs,
  runningJobs,
}: props): JSX.Element => {
  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>Running Tasks</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {runningJobs.map((job) => (
              <TaskCard
                taskName={job.taskName}
                taskID={job.taskID}
                variant="Running"
                startTime={job.startTime}
                endTime={job.endTime}
                runTime={job.runTime}
              />
            ))}
          </div>
          <div className="pt-4">
            <Button variant="outline">View all running tasks</Button>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Completed Tasks</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {completedJobs.map((job) => (
              <TaskCard
                taskName={job.taskName}
                taskID={job.taskID}
                variant="Completed"
                startTime={job.startTime}
                endTime={job.endTime}
                runTime={job.runTime}
              />
            ))}
          </div>
          
          <div className="pt-4">
            <Button variant="outline">View all completed tasks</Button>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Failed Tasks</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {failedJobs.map((job) => (
              <TaskCard
                taskName={job.taskName}
                taskID={job.taskID}
                variant="Failed"
                startTime={job.startTime}
                endTime={job.endTime}
                runTime={job.runTime}
              />
            ))}
          </div>
          <div className="pt-4">
            <Button variant="outline">View all failed tasks</Button>
          </div>
        </CardContent>
      </Card>
    </>
  );
};
