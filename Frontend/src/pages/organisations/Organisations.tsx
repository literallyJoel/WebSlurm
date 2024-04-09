import Nav from "@/components/Nav";

import { useQuery } from "react-query";
import { Link, Outlet, useParams } from "react-router-dom";

import { useAuthContext } from "@/providers/AuthProvider";
import { getAdminsOrgs } from "@/helpers/organisations";

import ListViewOrganisationCard from "@/components/organisations/ListViewOrganisationCard";

const Organisations = (): JSX.Element => {
  const { organisationId } = useParams();
  const authContext = useAuthContext();
  const token = authContext.getToken();

  const { data: allOrganisations } = useQuery("allJobs", () => {
    return getAdminsOrgs(token);
  });

  return (
    <>
      <Nav />
      <div className="flex h-screen w-full text-center">
        <nav className="w-80 bg-gray-100 overflow-y-auto dark:bg-gray-800 border-r dark:border-gray-700 h-full px-6 py-4">
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
    </>
  );
};

export default Organisations;
