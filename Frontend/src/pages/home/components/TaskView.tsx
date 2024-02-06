import type { Job } from "@/helpers/jobs";
import { Button } from "@/shadui/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/shadui/ui/card";
import TaskCard from "./TaskCard";

interface props {
  failedJobs: Job[];
  completedJobs: Job[];
  runningJobs: Job[];
}

const TaskView = ({
  failedJobs,
  completedJobs,
  runningJobs,
}: props): JSX.Element => (
  <>
    <Card>
      <CardHeader>
        <CardTitle>Running Jobs</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {runningJobs.length === 0
            ? "There are no jobs currently running"
            : runningJobs.map((job) => (
                <TaskCard
                  key={job.jobID}
                  name={job.jobName}
                  id={`${job.jobID}`}
                  variant="Running"
                  startTime={new Date(job.jobStartTime * 1000)}
                  runTime={new Date().getTime() - job.jobStartTime * 1000}
                />
              ))}
        </div>
        <div className="pt-4">
          {runningJobs.length !== 0 && (
            <Button variant="outline">View all Running Jobs</Button>
          )}
        </div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader>
        <CardTitle>Completed Jobs</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {completedJobs.map((job) => {
            return (
              <TaskCard
                key={job.jobID}
                name={job.jobName}
                id={`${job.jobID}`}
                variant="Completed"
                startTime={new Date(job.jobStartTime * 1000)}
                endTime={
                  job.jobCompleteTime
                    ? new Date(job.jobCompleteTime * 1000)
                    : undefined
                }
                runTime={
                  job.jobStartTime -
                  (job.jobCompleteTime ? job.jobCompleteTime * 1000 : 0)
                }
              />
            );
          })}
        </div>

        <div className="pt-4">
          <Button variant="outline">View all Completed Jobs</Button>
        </div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader>
        <CardTitle>Failed Jobs</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {failedJobs.map((job) => (
            <TaskCard
              key={job.jobID}
              name={job.jobName}
              id={`${job.jobID}`}
              variant="Failed"
              startTime={new Date(job.jobStartTime * 1000)}
            />
          ))}
        </div>
        <div className="pt-4">
          <Button variant="outline">View all Failed Jobs</Button>
        </div>
      </CardContent>
    </Card>
  </>
);

export default TaskView;
