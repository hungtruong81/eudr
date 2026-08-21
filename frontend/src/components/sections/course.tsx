import CourseCard from "@/components/course-card";
import Section from "@/components/section";
import { getBlogPosts } from "@/lib/blog";

import {
  getListCoursesApi
} from "@/lib/api";
import { formatCourse } from "@/lib/utils";
import { CourseConfig } from "@/lib/types";

export default async function CourseSection() {
  // const allPosts = await getBlogPosts();

  const order_by = "priority";
  const order_type = "desc";
  const response = await getListCoursesApi({ order_by, order_type });
      if (response.result != "success") {
        throw new Error(response.errorMessage);
      }

  const allCourses = response.data.records.map(formatCourse) as CourseConfig[];

  /* const articles = await Promise.all(
    allPosts.sort((a, b) => b.publishedAt.localeCompare(a.publishedAt))
  ); */

  return (
    <Section title="Khóa học" subtitle="Các khóa học nổi bật">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {allCourses.map((course, idx) => (
          <CourseCard key={course.courseId} course={course} priority={idx <= 1} />
        ))}
      </div>
    </Section>
  );
}
