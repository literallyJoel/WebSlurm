import Nav from "@/components/Nav";

import { useQuery } from "react-query";
import { Link, Outlet, useParams } from "react-router-dom";

import { useAuthContext } from "@/providers/AuthProvider";
import { getUserOrganisations } from "@/helpers/organisations";

import ListViewOrganisationCard from "@/components/organisations/ListViewOrganisationCard";

const Organisations = (): JSX.Element => {
  const { organisationId } = useParams();
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const user = authContext.getUser();
  const { data: allOrganisations } = useQuery("allJobs", () => {
    const orgsOne = getUserOrganisations(token, user.id, 2);
    const orgsTwo = getUserOrganisations(token, user.id, 1);
    return Promise.all([orgsOne, orgsTwo]).then((values) => {
      return values.flat();
    });
  });

  return (
    <div className="flex flex-col min-h-screen">
      <Nav />
      <div className="flex min-h-screen w-full text-center">
        <nav className="w-80 bg-gray-100 overflow-y-auto dark:bg-gray-800 border-r dark:border-gray-700 h-[100wh] px-6 py-4">
          <div className="flex justify-between">
            <h1 className="text-xl font-bold mb-4 w-full">
              Your Organisations
            </h1>
          </div>

          <ul className="space-y-2">
            {allOrganisations?.map((organisation) => (
              <Link
                to={`/organisations/${organisation.organisationId}`}
                key={organisation.organisationId}
                className={`flex hover:bg-slate-200 ${
                  `${organisationId}` === `${organisation.organisationId}`
                    ? "bg-slate-200"
                    : ""
                }`}
              >
                <ListViewOrganisationCard organisation={organisation} />
              </Link>
            ))}
          </ul>
        </nav>
        <main className="flex-grow p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Organisations;
