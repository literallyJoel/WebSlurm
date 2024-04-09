import { useQuery } from "react-query";
import { Button } from "@/components/shadui/ui/button";
import { FaPlus } from "react-icons/fa";
import Noty from "noty";
import { Link } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider";
import {
  getAllOrganisations,
  getUserMemberships,
} from "@/helpers/organisations";
import { useState } from "react";
import OrganisationCard from "@/components/organisations/OrganisationCard";

const Organisations = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const [organisationCount, setOrganisationCount] = useState(1);
  const { data: allOrganisations } = useQuery(
    "getAllOrganisations",
    () => {
      return getAllOrganisations(token);
    },
    {
      onSuccess: (data) => setOrganisationCount(data.length),
    }
  );

  const { data: userOrganisations } = useQuery(
    "getUserOrganisations",
    () => {
      return getUserMemberships(token);
    },
    {
      onError: () => {
        new Noty({
          type: "error",
          text: "Failed to fetch your memberships. Please try again later.",
          timeout: 4000,
        }).show();
      },
    }
  );

  return (
    <div className="w-full flex flex-col">
      <span className="text-2xl text-uol font-bold">Organisations</span>
      <div className="w-full flex flex-row justify-center p-4">
        <Link to="/organisations/create">
          <Button className="bg-tranparent border-green-600 border hover:bg-green-600 group transition-colors">
            <FaPlus className="text-green-600 group-hover:text-white transition-colors" />
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-4 gap-2">
        {allOrganisations?.map((organisation) => {
          return (
            <OrganisationCard
              key={organisation.organisationId}
              name={organisation.organisationName}
              id={organisation.organisationId}
              organisationCount={organisationCount}
              userMemberships={userOrganisations ?? []}
            />
          );
        })}
      </div>
    </div>
  );
};

export default Organisations;
