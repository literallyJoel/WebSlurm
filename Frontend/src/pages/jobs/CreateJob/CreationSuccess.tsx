import { CardHeader, CardContent, Card } from "@/shadui/ui/card";
import { Button } from "@/shadui/ui/button";
import Nav from "@/components/Nav";
import { Link } from "react-router-dom";
interface props {
  serverResponse: string;
}

export default function CreationSuccess({ serverResponse }: props) {
  return (
    <div className="flex flex-col w-full h-screen ">
      <Nav />
      <div className="flex flex-col w-full h-4/6 justify-center items-center pt-8">
        <Card className="w-10/12 h-3/4 flex flex-col justify-center">
          <CardHeader className="flex flex-row justify-center bg-white">
            <div className="flex justify-center items-center h-24 w-24 rounded-full bg-emerald-600">
              <CheckIcon className="h-16 w-16 text-white mx-auto" />
            </div>
          </CardHeader>
          <CardContent className="flex flex-col items-center justify-center p-4">
            <h1 className="text-4xl font-bold text-uol">
              Job Succesfully Created
            </h1>
            <p className="text-2xl text-uol">{serverResponse}</p>
            <div className="p-3">
              <Link to="/">
                <Button className="mt-4 bg-uol text-white hover:bg-emerald-600">
                  Return to Home
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function CheckIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <polyline points="20 6 9 17 4 12" />
    </svg>
  );
}
