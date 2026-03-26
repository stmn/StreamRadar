export function twitchCategoryUrl(name: string): string {
    const slug = name.toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    return 'https://www.twitch.tv/directory/category/' + slug;
}
