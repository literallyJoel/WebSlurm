import Nav from "@/components/Nav";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { useContext } from "react";
import TaskView from "./components/TaskView";
import { Button } from "@/shadui/ui/button";
import { Link } from "react-router-dom";

const Home = (): JSX.Element => {
  const { getUser } = useContext(AuthContext);
  const user = getUser();

  const _runningJobsTest = [
    { name: "Task1", id: "234234", startTime: new Date(2023, 10, 12, 15, 33) },
    { name: "Task2", id: "234234", startTime: new Date(2023, 11, 17, 12, 33) },
    { name: "Task3", id: "234234", startTime: new Date(2023, 11, 22, 17, 0) },
  ];

  const _completedJobsTest = [
    {
      name: "Task4",
      id: "234234",
      startTime: new Date(2023, 11, 12, 15, 33),
      endTime: new Date(2023, 11, 14, 15, 33),
    },
    {
      name: "Task5",
      id: "234234",
      startTime: new Date(2023, 12, 17, 12, 33),
      endTime: new Date(2023, 11, 14, 15, 33),
    },
    {
      name: "Task6",
      id: "234234",
      startTime: new Date(2023, 10, 16, 17, 33),
      endTime: new Date(2023, 11, 14, 15, 33),
    },
  ];

  const _failedJobsTest = [
    {
      name: "Task7",
      id: "234234",
      startTime: new Date(2023, 11, 12, 15, 33),
      endTime: new Date(2023, 10, 16, 17, 2),
    },
    {
      name: "Task8",
      id: "234234",
      startTime: new Date(2023, 12, 17, 12, 33),
      endTime: new Date(2023, 10, 16, 17, 2),
    },
    {
      name: "Task9",
      id: "234234",
      startTime: new Date(2023, 10, 16, 17, 33),
      endTime: new Date(2023, 10, 16, 17, 2),
    },
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
          <Link to="/jobs/create">
            <Button className="bg-uol">Create new Job</Button>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Home;
