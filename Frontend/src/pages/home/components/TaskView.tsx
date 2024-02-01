import { Job } from "@/pages/admin/settings/JobTypes/jobTypes";
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
          {runningJobs.map((job) => (
            <TaskCard
              name={job.name}
              id={job.id}
              variant="Running"
              startTime={job.startTime}
              endTime={job.endTime}
              runTime={job.runTime}
            />
          ))}
        </div>
        <div className="pt-4">
          <Button variant="outline">View all Running Jobs</Button>
        </div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader>
        <CardTitle>Completed Jobs</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {completedJobs.map((job) => (
            <TaskCard
              name={job.name}
              id={job.id}
              variant="Completed"
              startTime={job.startTime}
              endTime={job.endTime}
              runTime={job.runTime}
            />
          ))}
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
              name={job.name}
              id={job.id}
              variant="Failed"
              startTime={job.startTime}
              endTime={job.endTime}
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
