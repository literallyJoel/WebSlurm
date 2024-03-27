// import { useQuery } from "react-query";
// import { getJobTypes } from "../../../../helpers/jobTypes";

import { getAllOrganisations } from "@/helpers/organisations";
import { Button } from "@/shadui/ui/button";


import { FaPlus } from "react-icons/fa";
import { useQuery } from "react-query";

import { Link } from "react-router-dom";
import OrganisationCard from "./components/OrganisationCard";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

const Organisations = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const organisations = useQuery("getAllOrgs", () => {
    return getAllOrganisations(token);
  });
  return (
    <div className="w-full flex flex-col">
      <span className="text-2xl text-uol font-bold">Organisations</span>
      <div className="w-full flex flex-row justify-center p-4">
        <Link to="/admin/organisations/create">
          <Button className="bg-tranparent border-green-600 border hover:bg-green-600 group transition-colors">
            <FaPlus className="text-green-600 group-hover:text-white transition-colors" />
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-4">
        {organisations.data?.map((organisation) => (
          <OrganisationCard
            key={organisation.organisationID}
            id={organisation.organisationID}
            name={organisation.organisationName}
            ownerName={organisation.ownerName}
          />
        ))}
      </div>
    </div>
  );
};

export default Organisations;
