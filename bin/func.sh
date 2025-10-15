which gh > /dev/null || {
  echo "gh is not installed. Please install it from https://cli.github.com/" >&2
  exit
}

latest_version() {
  gh release list --order desc --json tagName --template '{{ range . }}{{ .tagName }}{{"\n"}}{{ end }}' | head -n 1
}

next_version() {
  local array=($(latest_version | tr . '\n'))
  array[2]=$((array[2]+1))
  echo $(local IFS=. ; echo "${array[*]}")
}
