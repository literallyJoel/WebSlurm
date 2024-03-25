import { Button } from "@/shadui/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { FaRegCheckCircle } from "react-icons/fa";
import { Link } from "react-router-dom";

interface props {
  orgId: string;
  orgName: string;
}
export const CreationSuccess = ({ orgId, orgName }: props): JSX.Element => {
  return (
    <div className="mt-10 mb-10 flex-grow">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>Organisation Created</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col items-center gap-4">
          <FaRegCheckCircle className="text-green-500" size={60} />
          <div>Succesfully created Organisation</div>
          <div>Organisation ID: {orgId}</div>
          <div>Organisation Name: {orgName}</div>
        </CardContent>
        <CardFooter className="justify-center">
          <Link to="/">
            <Button className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner">
              Back to home
            </Button>
          </Link>
        </CardFooter>
      </Card>
    </div>
  );
};
