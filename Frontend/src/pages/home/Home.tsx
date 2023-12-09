import { useContext } from "react";
import Nav from "../components/Nav";
import { TaskView } from "./components/TaskView";
import { AuthContext } from "@/providers/auth/AuthProvider";
import { Button } from "@/shadui/ui/button";
export default function Home() {
  const {getUser} = useContext(AuthContext);
  const user = getUser();

  const _runningJobsTest = [
    { taskName: "Task1", taskID: "234234", startTime: "09/12/23 13:15" },
    { taskName: "Task2", taskID: "234234", startTime: "09/12/23 14:15" },
    { taskName: "Task3", taskID: "234234", startTime: "09/12/23 15:15" },
  ];

  const _completedJobsTest = [
    { taskName: "Task4", taskID: "234234", startTime: "09/12/23 13:15" },
    { taskName: "Task5", taskID: "234234", startTime: "09/12/23 14:15" },
    { taskName: "Task6", taskID: "234234", startTime: "09/12/23 15:15" },
  ];

  const _failedJobsTest = [
    { taskName: "Task7", taskID: "234234", startTime: "09/12/23 13:15" },
    { taskName: "Task8", taskID: "234234", startTime: "09/12/23 14:15" },
    { taskName: "Task9", taskID: "234234", startTime: "09/12/23 15:15" },
  ];
  return (
    <div className="flex flex-col w-full min-h-screen">
      <Nav />
      <div>
        <div className="flex flex-col items-center text-uol text-2xl font-bold pt-8">
          Welcome, {user.name.split(" ")[0]}.
        </div>
        <div className="flex flex-col items-center text-uol text-lg">
          Here's your job overview.
        </div>
        <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-3 p-8">
          <TaskView
            runningJobs={_runningJobsTest}
            completedJobs={_completedJobsTest}
            failedJobs={_failedJobsTest}
          />
        </div>
        <div className="flex flex-col items-center">
          <Button className="bg-uol">Create new Job</Button>
        </div>
      </div>
    </div>
  );
}
