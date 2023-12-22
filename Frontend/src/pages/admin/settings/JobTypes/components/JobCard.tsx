import { Badge } from "@/shadui/ui/badge";
import { Button } from "@/shadui/ui/button";
import { Card, CardFooter, CardHeader, CardTitle } from "@/shadui/ui/card";

interface props{
    id: string;
    name: string;
    createdBy: string;
}


const JobCard = ({id, name, createdBy}: props):JSX.Element =>{
    return(
        <Card>
            <CardHeader>
                <div className="flex flex-row justify-between w-full gap-2 border-b border-b-uol pb-2">
                    <Badge className="bg-uol min-w-3/12 text-[0.5rem] justify-center">
                        ID: {id}
                    </Badge>
                    <Badge className="bg-uol justify-center text-[0.5rem] min-w-3/12 self-end">
                        Created By: {createdBy}
                    </Badge>
                </div>
                <CardTitle className="pt-2">{name}</CardTitle>
            </CardHeader>

            <CardFooter className="flex flex-col justify-between items-center">
                <Button className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white">
                    Modify
                </Button>
            </CardFooter>
        </Card>
    )
}

export default JobCard;