import { CardHeader, CardContent, Card } from "@/components/shadui/ui/card";
import { Button } from "@/components/shadui/ui/button";
import Nav from "@/components/Nav";
import { Link } from "react-router-dom";

export default function CreationFailure() {
  return (
    <div className="flex flex-col w-full h-screen ">
      <Nav />
      <div className="flex flex-col w-full h-4/6 justify-center items-center pt-8">
        <Card className="w-5/12 h-1/2 flex flex-col justify-center">
          <CardHeader className="flex flex-row justify-center bg-white">
            <div className="flex justify-center items-center h-24 w-24 rounded-full bg-rose-600">
              <ErrorIcon className="h-16 w-16 text-white mx-auto" />
            </div>
          </CardHeader>
          <CardContent className="flex flex-col items-center justify-center p-4">
            <h1 className="text-4xl font-bold text-uol">Job Creation Failed</h1>
            <p className="text-2xl text-uol">
              Something went wrong, please try again later
            </p>
            <Link to="/">
              <Button className="mt-4 bg-uol text-white hover:bg-rose-600">
                Return to Home
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function ErrorIcon(props: React.SVGProps<SVGSVGElement>) {
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
      <path d="M18 6 6 18" />
      <path d="m6 6 12 12" />
    </svg>
  );
}
