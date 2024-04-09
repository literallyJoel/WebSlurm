import { Organisation } from "@/helpers/organisations";
import { Badge } from "../shadui/ui/badge";

interface props {
  organisation: Organisation;
}
const ListViewOrganisationCard = ({ organisation }: props): JSX.Element => {
  return (
    <div className="w-full flex flex-col justify-center p-1 border border-gray-600 gap-0 rounded-md shadow-md">
      <div className="w-full flex flex-row justify-between p-2">
        <Badge>ID:{organisation.organisationId}</Badge>
      </div>
      <div>{organisation.organisationName}</div>
    </div>
  );
};

export default ListViewOrganisationCard;
