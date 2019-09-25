-- avg_up
select * from (
	select
		round(avg(a.up), 2) avg_up,
		a.code,
		b.type
	from
		hsab a
	left join hsab b on
		a.code = b.code
		and a.type = b.type
		and b.date = curdate()
	where
		a.date <> curdate()
		and b.price is not null
		and LEFT(a.code,3) NOT IN (200,300,688,900) 
		and left(b.name, 1) not in ('*', 'S') 
		and right(b.name, 1)<>'退'
	group by
		a.code
	order by
		avg_up desc
) as a
left join hsab b on
	a.code = b.code
	and a.type = b.type
where avg_up<=8