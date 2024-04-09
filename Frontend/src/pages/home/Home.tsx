import Nav from "@/components/Nav";
import TaskView from "@/components/home/TaskView";
import { Button } from "@/components/shadui/ui/button";
import { Link } from "react-router-dom";
import { useQuery } from "react-query";
import {
  getCompletedJobs,
  getFailedJobs,
  getRunningJobs,
} from "@/helpers/jobs";
import { useAuthContext } from "@/providers/AuthProvider";
import { getOrganisationUser } from "@/helpers/organisations";

const Home = (): JSX.Element => {
  const { getUser, getToken } = useAuthContext();
  const user = getUser();
  const token = getToken();

  const { data: completedJobs } = useQuery("homeCompletedJobs", () => {
    return getCompletedJobs(token, 3);
  });

  const { data: runningJobs } = useQuery("homeRunningJobs", () => {
    return getRunningJobs(token, 3);
  });

  const { data: failedJobs } = useQuery("homeFailedJobs", () => {
    return getFailedJobs(token, 3);
  });

  const {data: isOrgAdmin} = useQuery("isOrgAdmin", () => {
    return getOrganisationUser
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
            runningJobs={runningJobs ?? []}
            completedJobs={completedJobs ?? []}
            failedJobs={failedJobs ?? []}
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
