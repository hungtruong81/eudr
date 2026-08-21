import { CourseConfig } from "@/lib/types";
import { formatDate, formatPrice } from "@/lib/utils";
import Image from "next/image";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import {AddCartButton} from "@/components/cart/add-cart-button";
import { format } from "path";

export default function CourseCard({
  course,
  priority,
}: {
  course: CourseConfig;
  priority?: boolean;
}) {

  if (!course) {
    return <div className="bg-gray-200 h-[180px] mb-4 rounded" />;
  }

  return (
    <Link href={`/khoa-hoc/${course.slug}`} className="block">
      <div className="h-full bg-background rounded-lg p-4 border hover:shadow-xs transition-shadow duration-200">
        {course.filePath && (
          <Image
            className="rounded-t-lg object-cover border"
            src={course.filePath}
            width={1200}
            height={630}
            alt={course.name}
            priority={priority}
          />
        )}
        {/* {!course.filePath && <div className="bg-gray-200 h-[180px] mb-4 rounded" />} */}
        <h3 className="text-xl font-semibold mb-2">{course.name}</h3>
        <p className="text-foreground mb-4">{course.description}</p>
        <div className="flex items-center justify-between">
          {course.priceOld >0 && <span className="line-through text-sm text-muted-foreground">{formatPrice(course.priceOld)}</span>}
          <span className="text-lg font-bold text-primary">{formatPrice(course.price)}</span>

          <AddCartButton course={course} />

        </div>
      </div>
    </Link>
  );
}
