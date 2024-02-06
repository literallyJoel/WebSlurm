import Nav from "@/components/Nav";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { useContext } from "react";
import TaskView from "./components/TaskView";
import { Button } from "@/shadui/ui/button";
import { Link } from "react-router-dom";
import { useQuery } from "react-query";
import {
  getCompletedJobs,
  getFailedJobs,
  getRunningJobs,
} from "@/helpers/jobs";
const Home = (): JSX.Element => {
  const { getUser } = useContext(AuthContext);
  const user = getUser();
  const token = useContext(AuthContext).getToken();
  const completedJobs = useQuery("getCompletedJobs", () => {
    return getCompletedJobs(token, 3, user.id);
  });

  const runningJobs = useQuery("getRunningJobs", () => {
    return getRunningJobs(token, 3, user.id);
  });

  const failedJobs = useQuery("getFailedJobs", () => {
    return getFailedJobs(token, 3, user.id);
  });

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
            runningJobs={runningJobs.data ?? []}
            completedJobs={completedJobs.data ?? []}
            failedJobs={failedJobs.data ?? []}
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
