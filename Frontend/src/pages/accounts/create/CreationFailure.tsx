import Nav from "@/components/Nav";
import { Button } from "@/shadui/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { MdErrorOutline } from "react-icons/md";

export const CreationFailure = (): JSX.Element => {
  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 flex-grow mb-10">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle className="text-center">An error occurred</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col items-center">
            <MdErrorOutline size={60} className="text-red-500" />
            <span>Something went wrong. Please try again later.</span>
          </CardContent>
          <CardFooter className="justify-center">
            <Button
              onClick={() => window.history.back()}
              className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
            >
              Back
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};
